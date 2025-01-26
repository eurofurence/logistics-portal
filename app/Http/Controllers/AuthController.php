<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Whitelist;
use App\Models\Department;
use App\Models\IdpRankSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function login()
    {
        return Socialite::driver('identity')->redirect();
    }

    public function getIDPGroupUser(string $group_id, ?string $rank_filter = null): array|null
    {
        $query = [];

        if ($rank_filter != null) {
            $query['filter[rank]'] = $rank_filter;
        }

        $response = Http::withHeaders([
            #TODO: insert token
            'Authorization' => 'Bearer ' . 'token',
        ])->get('https://identity.eurofurence.org/api/v1/groups/N9OY0K8O0V2R1P7L/users');

        dd($response);

        if ($response->successful()) {
            return json_decode($response->json()); // Antwort als Array
        } else {
            // Fehlerbehandlung
            dd($response->status(), $response->body());
        }
    }

    public function loginCallback()
    {
        $user = Socialite::driver('identity')->user();
        $local_user = User::where('ex_id', $user['sub'])->first();

        /*
        $idp_user_groups = $user['groups'];
        $idp_user_groups_with_ranks = array();

        array_unique($idp_user_groups);

        $test = array();
        foreach ($idp_user_groups as $group) {
            $rank_inside_group = null;

            $test[] = $this->getIDPGroupUser($group, 'member');
        }
        */

        $ex_email_verified = $user['email_verified'];

        if ($ex_email_verified == true) {
            $ex_email_verified = Carbon::now();
        } else {
            $ex_email_verified = null;
        }

        if ($local_user) {
            $old_email = $local_user['email'];

            if ($old_email != $user['email']) {
                Whitelist::where('email', $old_email)->update([
                    'email' => $user['email']
                ]);
            }
        }

        if (!Whitelist::where('email', $user['email'])->exists()) {
            return abort(403, __('middleware.not_on_whitelist'));
        }

        $update_array = [
            'ex_groups' => $user['groups'],
            'email' => $user['email'],
            'avatar' => $user['avatar'],
            'name' => $user['name'],
            'last_login' => Carbon::now(),
            'email_verified_at' => $ex_email_verified,
        ];

        $f_user_id = null;

        $updated = User::where('ex_id', $user['sub'])->update(
            $update_array
        );

        if (!$updated) {
            if (User::where('ex_id', $user['sub'])->withTrashed()->exists()) {
                abort(403, __('middleware.account_deleted'));
            }

            $user = User::create([
                'ex_id' => $user['sub'],
                'ex_groups' => $user['groups'],
                'email' => $user['email'],
                'avatar' => $user['avatar'],
                'name' => $user['name'],
                'last_login' => Carbon::now(),
                'email_verified_at' => $ex_email_verified,
            ]);

            $updated = true;
            $f_user_id = $user->id;
        }

        if (!$f_user_id) {
            $f_user_id = User::where('ex_id', $user['sub'])->first('id')->id;
        }

        if ($updated == true && ($user != null)) {
            Auth::loginUsingId($f_user_id);
            $auth_user = Auth::user();
            $external_groups = $auth_user->ex_groups;

            if (!$auth_user->separated_rights) {
                if ($external_groups) {
                    $exists = IdpRankSync::whereIn('idp_group', $external_groups)->exists();

                    if ($exists) {
                        $syncs = IdpRankSync::whereIn('idp_group', $external_groups)->where('active', true)->get(['local_role']);

                        $roles = [];

                        foreach ($syncs as $sync) {
                            if (Role::where('id', $sync['local_role'])->exists()) {
                                $name = Role::where('id', $sync['local_role'])->first('name')->name;

                                if (!in_array($name, $roles)) {
                                    $roles[] = $name;
                                }
                            }
                        }

                        Auth::user()->syncRoles($roles);
                    }
                }
            }

            if (!$auth_user->separated_departments) {
                if ($external_groups) {
                    // Hole alle existierenden ipd_group_id-Werte aus der departments-Tabelle
                    $existing_ipd_group_ids = Department::all()->pluck('idp_group_id')->toArray();

                    // Filtere die external_groups, um nur Einträge mit nicht NULL Werten zu behalten
                    $filtered_external_groups = array_filter($external_groups, function ($value) {
                        return !is_null($value);
                    });

                    // Filtere die external_groups, um nur existierende Werte zu behalten
                    $valid_external_groups = array_filter($filtered_external_groups, function ($group_id) use ($existing_ipd_group_ids) {
                        return in_array($group_id, $existing_ipd_group_ids);
                    });

                    $department_ids = array();
                    foreach ($valid_external_groups as $group_id) {
                        $department = Department::where('idp_group_id', $group_id)->first('id');

                        if ($department) {
                            $department_ids[] = $department->id;
                        }
                    }

                    $auth_user->departments()->sync($department_ids);
                }
            }

            return redirect()->route('filament.app.pages.dashboard');
        }

        return abort(500, 'Error while login, please contact administrator');
    }

    public function logout()
    {
        Auth::logout();
        Session::flush();

        return redirect('https://identity.eurofurence.org/oauth2/sessions/logout');
    }


    // Frontchannel Logout
    public function logoutCallback()
    {
        Auth::logout();
        Session::flush();

        return redirect('https://identity.eurofurence.org');
    }
}

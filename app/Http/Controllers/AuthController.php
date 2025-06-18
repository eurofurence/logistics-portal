<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Whitelist;
use App\Models\Department;
use App\Models\IdpRankSync;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function login()
    {
        return Socialite::driver('identity')->redirect();
    }

    public function loginCallback()
    {
        $user = Socialite::driver('identity')->user();
        $local_user = User::where('ex_id', $user['sub'])->first();

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
                    // Get all existing ipd_group_id values from the departments table
                    $existing_ipd_group_ids = Department::all()->pluck('idp_group_id')->toArray();

                    // Filter the external_groups to keep only entries with non-NULL values
                    $filtered_external_groups = array_filter($external_groups, function ($value) {
                        return !is_null($value);
                    });

                    // Filter the external_groups to keep only existing values
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

import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/Card';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import UpdateProfilePhotoForm from './Partials/UpdateProfilePhotoForm';

interface ProfileUser {
    surname: string;
    first_name: string;
    middle_name: string | null;
    suffix: string | null;
    contact_number: string | null;
    employee_number: string | null;
    email: string;
    username: string | null;
    profile_photo_url: string | null;
}

export default function Edit({ user }: { user: ProfileUser }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">My Profile</h1>}>
            <Head title="Profile" />

            <div className="mx-auto max-w-3xl space-y-6">
                <Card>
                    <CardContent>
                        <UpdateProfilePhotoForm
                            photoUrl={user.profile_photo_url}
                            name={`${user.first_name} ${user.surname}`}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardContent>
                        <UpdateProfileInformationForm user={user} />
                    </CardContent>
                </Card>

                <Card>
                    <CardContent>
                        <UpdatePasswordForm />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

import { FormEventHandler } from 'react';
import { Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';

interface ProfileUser {
    surname: string;
    first_name: string;
    middle_name: string | null;
    suffix: string | null;
    contact_number: string | null;
    employee_number: string | null;
    email: string;
    username: string | null;
}

export default function UpdateProfileInformationForm({ user }: { user: ProfileUser }) {
    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        surname: user.surname ?? '',
        first_name: user.first_name ?? '',
        middle_name: user.middle_name ?? '',
        suffix: user.suffix ?? '',
        contact_number: user.contact_number ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('profile.update'), { preserveScroll: true });
    };

    return (
        <section>
            <header>
                <h2 className="text-lg font-medium text-slate-900">Profile Information</h2>
                <p className="mt-1 text-sm text-slate-900">
                    Employee number, email, and username are managed by the College
                    Administrator via User Management.
                </p>
            </header>

            <div className="mt-4 grid grid-cols-2 gap-4 text-sm text-slate-900">
                <div>
                    <span className="block text-xs uppercase tracking-wide">Employee No.</span>
                    {user.employee_number}
                </div>
                <div>
                    <span className="block text-xs uppercase tracking-wide">Username</span>
                    {user.username}
                </div>
                <div className="col-span-2">
                    <span className="block text-xs uppercase tracking-wide">Email</span>
                    {user.email}
                </div>
            </div>

            <form onSubmit={submit} className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor="surname" value="Surname" />
                    <TextInput
                        id="surname"
                        className="mt-1 block w-full"
                        value={data.surname}
                        onChange={(e) => setData('surname', e.target.value)}
                        required
                    />
                    <InputError message={errors.surname} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="first_name" value="First Name" />
                    <TextInput
                        id="first_name"
                        className="mt-1 block w-full"
                        value={data.first_name}
                        onChange={(e) => setData('first_name', e.target.value)}
                        required
                    />
                    <InputError message={errors.first_name} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="middle_name" value="Middle Name" />
                    <TextInput
                        id="middle_name"
                        className="mt-1 block w-full"
                        value={data.middle_name}
                        onChange={(e) => setData('middle_name', e.target.value)}
                    />
                    <InputError message={errors.middle_name} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="suffix" value="Suffix" />
                    <TextInput
                        id="suffix"
                        className="mt-1 block w-full"
                        value={data.suffix}
                        onChange={(e) => setData('suffix', e.target.value)}
                    />
                    <InputError message={errors.suffix} className="mt-2" />
                </div>

                <div className="sm:col-span-2">
                    <InputLabel htmlFor="contact_number" value="Contact Number" />
                    <TextInput
                        id="contact_number"
                        className="mt-1 block w-full"
                        value={data.contact_number}
                        onChange={(e) => setData('contact_number', e.target.value)}
                    />
                    <InputError message={errors.contact_number} className="mt-2" />
                </div>

                <div className="flex items-center gap-4 sm:col-span-2">
                    <PrimaryButton disabled={processing}>Save</PrimaryButton>
                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-slate-900">Saved.</p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}

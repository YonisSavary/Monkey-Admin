# Monkey-Admin
Admin Interface for YonisSavary\Monkey Framework

## How to install-it

As Monkey allow you to have multiples apps connected to the framework, it is pretty easy 
to add Monkey-Admin to your app

At first, you can put the `admin` directory where you want in your project,
just **remember the relative path from your project root**

Then, you can add it in your `monkey.json` by adding it relative path in `app_directory`

Note: if your `app_directory`  is a string, you can make an array with the current value and the admin path

If you `cached_apploader` is `true`, you need to regenerate a new cache file by deleting 
the current one (`config/cached_apploader.json` by default)

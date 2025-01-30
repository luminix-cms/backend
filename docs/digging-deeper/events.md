# Events

Luminix Backend emits several events during the API request lifecycle. These events can be used to monitor and log API activity, perform additional processing, or trigger custom actions. The events are dispatched using Laravel's event system, so you can listen for them in your application by creating event listeners.

```bash
php artisan make:listner AddUploadedPictureToStorage --event="\Luminix\Backend\Events\SavedResource"
```




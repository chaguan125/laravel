<?php

namespace App\Librarys;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redis;
use Mews\Captcha\Captcha;

class CaptchaDiy extends Captcha{
        
    /**
     * Create captcha image
     *
     * @param string $config
     * @param bool $api
     * @return array|mixed
     * @throws Exception
     */
    public function create(string $config = 'default', bool $api = false)
    {
        $this->backgrounds = $this->files->files(resource_path('captcha/assets/backgrounds'));
        $this->fonts = $this->files->files($this->fontsDirectory);

        if (version_compare(app()->version(), '5.5.0', '>=')) {
            $this->fonts = array_map(function ($file) {
                /* @var File $file */
                return $file->getPathName();
            }, $this->fonts);
        }

        $this->fonts = array_values($this->fonts); //reset fonts array index

        $this->configure($config);

        $generator = $this->generate();
        $this->text = $generator['value'];

        $this->canvas = $this->imageManager->canvas(
            $this->width,
            $this->height,
            $this->bgColor
        );

        if ($this->bgImage) {
            $this->image = $this->imageManager->make($this->background())->resize(
                $this->width,
                $this->height
            );
            $this->canvas->insert($this->image);
        } else {
            $this->image = $this->canvas;
        }

        if ($this->contrast != 0) {
            $this->image->contrast($this->contrast);
        }

        $this->text();

        $this->lines();

        if ($this->sharpen) {
            $this->image->sharpen($this->sharpen);
        }
        if ($this->invert) {
            $this->image->invert();
        }
        if ($this->blur) {
            $this->image->blur($this->blur);
        }
        if ($api) {
            Redis::set('captcha_record_' .(tenant('id') ?? 0).'_'.$generator['key'], $generator['value'], 'EX', $this->expire);
        }

        return $api ? [
            'sensitive' => $generator['sensitive'],
            'key' => $generator['key'],
            'img' => $this->image->encode('data-url')->encoded
        ] : $this->image->response('png', $this->quality);
    }


    /**
     * Captcha check
     *
     * @param string $value
     * @param string $key
     * @param string $config
     * @return bool
     */
    public function check_api($value, $key, $config = 'default'): bool
    {
        if (!Redis::get('captcha_record_'.(tenant('id') ?? 0).'_'. $key)) {
            return false;
        }else{
            
            Redis::del('captcha_record_' .(tenant('id') ?? 0).'_'. $key);
        }

        $this->configure($config);

        if(!$this->sensitive) $value = $this->str->lower($value);
        if($this->encrypt) $key = Crypt::decrypt($key);
        return $this->hasher->check($value, $key);
    }

}
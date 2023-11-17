<?php

namespace Tests\Feature\Assets;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Statamic\Contracts\Assets\Asset;
use Statamic\Events\AssetDeleted;
use Statamic\Events\AssetReuploaded;
use Statamic\Events\AssetSaved;
use Statamic\Facades\Glide;
use Statamic\Imaging\PresetGenerator;
use Statamic\Listeners\ClearAssetGlideCache;
use Tests\TestCase;

class ClearAssetGlideCacheTest extends TestCase
{
    /** @test */
    public function it_subscribes()
    {
        $events = Mockery::mock(Dispatcher::class);
        $events->shouldReceive('listen')->with(AssetReuploaded::class, [ClearAssetGlideCache::class, 'handleReuploaded'])->once();
        $events->shouldReceive('listen')->with(AssetDeleted::class, [ClearAssetGlideCache::class, 'handleDeleted'])->once();
        $events->shouldReceive('listen')->with(AssetSaved::class, [ClearAssetGlideCache::class, 'handleSaved'])->once();

        (new ClearAssetGlideCache(app(PresetGenerator::class)))->subscribe($events);
    }

    /** @test */
    public function it_clears_when_deleting()
    {
        $asset = Mockery::mock(Asset::class);
        Glide::shouldReceive('clearAsset')->with($asset)->once();

        (new ClearAssetGlideCache(app(PresetGenerator::class)))->handleDeleted(new AssetDeleted($asset));
    }

    /** @test */
    public function it_clears_when_reuploading()
    {
        $asset = Mockery::mock(Asset::class);
        Glide::shouldReceive('clearAsset')->with($asset)->once();

        (new ClearAssetGlideCache(app(PresetGenerator::class)))->handleReuploaded(new AssetReuploaded($asset));
    }

    /** @test */
    public function it_clears_when_focus_is_added()
    {
        $asset = Mockery::mock(Asset::class);
        $asset->shouldReceive('getOriginal')->with('data.focus')->once()->andReturnNull();
        $asset->shouldReceive('get')->with('focus')->once()->andReturn('50-50-1');
        $asset->shouldReceive('id')->twice()->andReturn('123');

        Glide::shouldReceive('clearAsset')->with($asset)->once()->globally()->ordered();
        $this->mock(PresetGenerator::class)->shouldReceive('generate')->withArgs(fn ($arg1) => $arg1->id() === $asset->id())->once()->globally()->ordered();

        (new ClearAssetGlideCache(app(PresetGenerator::class)))->handleSaved(new AssetSaved($asset));
    }

    /** @test */
    public function it_clears_when_focus_changes()
    {
        $asset = Mockery::mock(Asset::class);
        $asset->shouldReceive('getOriginal')->with('data.focus')->once()->andReturn('75-25-1');
        $asset->shouldReceive('get')->with('focus')->once()->andReturn('50-50-1');
        $asset->shouldReceive('id')->twice()->andReturn('123');

        Glide::shouldReceive('clearAsset')->with($asset)->once()->globally()->ordered();
        $this->mock(PresetGenerator::class)->shouldReceive('generate')->withArgs(fn ($arg1) => $arg1->id() === $asset->id())->once()->globally()->ordered();

        (new ClearAssetGlideCache(app(PresetGenerator::class)))->handleSaved(new AssetSaved($asset));
    }

    /** @test */
    public function it_doesnt_clear_focus_stays_the_same()
    {
        $asset = Mockery::mock(Asset::class);
        $asset->shouldReceive('getOriginal')->with('data.focus')->once()->andReturn('75-25-1');
        $asset->shouldReceive('get')->with('focus')->once()->andReturn('75-25-1');

        Glide::shouldReceive('clearAsset')->with($asset)->never()->globally()->ordered();
        $this->mock(PresetGenerator::class)->shouldNotHaveReceived('generate');

        (new ClearAssetGlideCache(app(PresetGenerator::class)))->handleSaved(new AssetSaved($asset));
    }

    /** @test */
    public function it_clears_when_focus_is_removed()
    {
        $asset = Mockery::mock(Asset::class);
        $asset->shouldReceive('getOriginal')->with('data.focus')->once()->andReturn('75-25-1');
        $asset->shouldReceive('get')->with('focus')->once()->andReturnNull();
        $asset->shouldReceive('id')->twice()->andReturn('123');

        Glide::shouldReceive('clearAsset')->with($asset)->once()->globally()->ordered();
        $this->mock(PresetGenerator::class)->shouldReceive('generate')->withArgs(fn ($arg1) => $arg1->id() === $asset->id())->once()->globally()->ordered();

        (new ClearAssetGlideCache(app(PresetGenerator::class)))->handleSaved(new AssetSaved($asset));
    }
}

/*=========================================================================================
    Description  : Simple action repeater plugin.
    --------------------------------------------------------------------------------------
    Author       : Rei Junior
    Created Date : 07/21/2023
    Edited By    : Rei Junior
    Edited Date  : 11/13/2023
    Remarks      : Re-coded
==========================================================================================*/

class Repeater
{
    constructor()
    {
        this.event = {
            beforeRepeat: function (){},
            repeat: function (){},
            countdown: function (){},
            afterRepeat: function (){}
        };

        this.countdownCounter = 0;
        this.loopCounter = 0;

        this.repeatCount = 0;

        this.terminate = false;
    }

    interval(seconds, repeat = 'infinite')
    {
        this.interval = seconds;
        this.loop = repeat;
        return this;
    }

    beforeRepeat(fn)
    {
        this.event.beforeRepeat = fn;
        return this;
    }

    repeat(fn)
    {
        this.event.repeat = async function (){
            this.repeatCount++;
            await fn(this);

            // definite loop counter
            if(this.loop !== 'infinite'){
                this.loopCounter--;
            }
        }.bind(this);

        return this;
    }

    countdown(fn)
    {
        this.event.countdown = fn;
        return this;
    }

    afterRepeat(fn)
    {
        this.event.afterRepeat = fn;
        return this;
    }

    tick()
    {
        this.countdownCounter--;
        return this;
    }

    async execute()
    {
        // terminate repeater if value equals to zero (0) or below
        if(this.loop !== 'infinite' && this.loop <= 0){
            return false;
        }

        // init values
        this.countdownCounter = this.interval;
        this.loopCounter = this.loop;

        await this.event.beforeRepeat();
        await this.event.repeat();

        const handle = async function (){
            // countdown event
            if (this.countdownCounter > 0){
                await this.event.countdown(this.countdownCounter);
                await new Promise(resolve => setTimeout(resolve, 1000));
                this.tick();
            }

            // repeat event
            if(this.countdownCounter <= 0){
                await this.event.repeat();

                // reset counter
                if(this.loop === 'infinite' || this.loopCounter > 0){
                    this.countdownCounter = this.interval;
                }
            }

            // rerun process
            if(this.loop === 'infinite' || this.loopCounter > 0){
                await handle();
            }
        }.bind(this);

        await handle();

        if(!this.terminate){
            // triggers only on definite loop; or stop
            await this.event.afterRepeat();
        }
    }

    trigger()
    {
        this.countdownCounter = 1;
        return this;
    }

    stop()
    {
        this.loop = 0;
        this.loopCounter = 0;
        this.terminate = true;
        return this;
    }
}

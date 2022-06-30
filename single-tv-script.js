/* live */

jQuery(document).ready(function($) {


    var myPlayer = videojs('main_video');
    let video = myPlayer.eventBusEl_.querySelector('video');

    video.addEventListener('ended', function() {
        console.log('END');
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            'event': 'pnimatv',
            'category': 'video',
            'action': 'סיום סרטון',
            'video': decodeURI(window.location.pathname.split('tv/').pop())

        })


    });
    var halfVideo = false;
    var videoStart = false;
    $(video).on(
        "timeupdate",
        function() {
            onTrackedVideoFrame(this.currentTime, this.duration);
        }
    );

    function onTrackedVideoFrame(currentTime, duration) {
        if (currentTime >= duration / 2 && !halfVideo) {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                'event': 'pnimatv',
                'category': 'video',
                'action': 'אמצע סרטון',
                'video': decodeURI(window.location.pathname.split('tv/').pop())

            })
            halfVideo = true;
        }

    };



    video.addEventListener('play', (event) => {
        if (!videoStart) {
            videoStart = true;
            console.log("here");
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                'event': 'pnimatv',
                'category': 'video',
                'action': 'התחלת סרטון',
                'video': decodeURI(window.location.pathname.split('tv/').pop())

            })
        }
    });
    createSlider();
    addBackButton();
    setGradiantOnStop();
    checkIfLogdin();
    addSkipAndBackInVideo();


    function addSkipAndBackInVideo() {
        var backBtn = addNewButton({
            id: "backBtn"
        });
        backBtn.onclick = function() {
            let cur = myPlayer.currentTime();
            myPlayer.currentTime(cur - 10);
        };
        var skipBtn = addNewButton({
            id: "skipBtn"
        });
        skipBtn.onclick = function() {
            let cur = myPlayer.currentTime();
            myPlayer.currentTime(cur + 10);
        };
    }

    function addNewButton(data) {
        var controlBar,
            newElement = document.createElement('button'),
            newLink = document.createElement('span');

        newElement.id = data.id;
        newElement.className = 'vjs-control';
        newElement.appendChild(newLink);
        controlBar = document.getElementsByClassName('vjs-control-bar')[0];
        insertBeforeNode = document.getElementsByClassName('vjs-volume-panel')[0];
        controlBar.insertBefore(newElement, insertBeforeNode);

        return newElement;
    }

    function addBackButton() {
        var container,
            newElement = document.createElement('button'),
            newLink = document.createElement('a'),
            newSpan = document.createElement('span');

        newElement.id = "backToLastPageBtn";
        newElement.className = 'vjs-control';
        newElement.appendChild(newLink);

        let historyUrl = document.referrer;
        let domain = historyUrl.substring(historyUrl.indexOf('https://') + 8);
        //TODO change in styging

        if (domain.indexOf('pnimatv') > -1)
            newLink.href = "javascript:history.back()";
        else
            newLink.href = "/";

        newLink.appendChild(newSpan);
        container = document.getElementsByClassName('video-js')[0];
        insertBeforeNode = document.getElementsByClassName('vjs-big-play-button')[0];
        container.insertBefore(newElement, insertBeforeNode);

        return newElement;
    }

    function setGradiantOnStop() {
        myPlayer.on("pause", function(e) {
            document.getElementsByClassName('vjs-tech')[0].style.filter = 'opacity(0.5)';
        });

        myPlayer.on("play", function(e) {
            document.getElementsByClassName('vjs-tech')[0].style.filter = 'opacity(1)';
        });
    }

    function createSlider() {
        var num_more_videos = $('.more-videos-in-series').children('.more-videos-item').length;
        var slides_to_show = num_more_videos > 2 ? 3 : num_more_videos;
        $('.more-videos-in-series').slick({
            infinite: false,
            arrows: true,
            rtl: true,
            slidesToScroll: 1,
            slidesToShow: slides_to_show,
            // lazyLoad: 'ondemand',
        });

        let prevArrow = $(".slick-arrow.slick-prev");
        let nextArrow = $(".slick-arrow.slick-next");
        toggleArrowDisplay(prevArrow);
        hideArrows(prevArrow, nextArrow);
        addGradientToLastImg();


        function hideArrows(prevArrow, nextArrow) {
            $('.more-in-series').on('swipe', function(event, slick, direction) {
                toggleArrowDisplay(prevArrow);
                toggleArrowDisplay(nextArrow);
                addGradientToLastImg();
            });

            $('.slick-arrow').on('click', function() {
                toggleArrowDisplay(prevArrow);
                toggleArrowDisplay(nextArrow);
                addGradientToLastImg();
            });
        }

        function toggleArrowDisplay(arrow) {
            if ((arrow).is(".slick-disabled")) {
                arrow.hide();
            } else {
                arrow.show();
            }
        }

        function addGradientToLastImg() {
            let activeSlides = $(".slick-active");
            for (var active of activeSlides) {
                active.classList.remove("left-gradient");
            }

            let lastToShow = activeSlides[activeSlides.length - 1];
            if (lastToShow && !lastToShow.matches(":last-child"))
                lastToShow.classList.add("left-gradient");
        }
    }

    function checkIfLogdin() {
        $.ajax({
            type: 'POST',
            url: "/wp-admin/admin-ajax.php",
            data: {
                action: 'is_login'
            },
            success: function(response) {
                if (!response.data.result) {
                    callPopupIfNotFree();
                }
            }
        });
    }

    function callPopupIfNotFree() {
        if (!$('.single-video').hasClass("free")) {
            showPopup(10);
        }
    }

    function showPopup(pausetime) {
        myPlayer.on('timeupdate', function(e) {
            if (myPlayer.currentTime() >= pausetime) {
                myPlayer.pause();
                if (myPlayer.isFullscreen())
                    myPlayer.exitFullscreen();
                $(".wrap-popup").show();
                myPlayer.src = "";
                $(".overlay").css("z-index", 5);
                controlBar = document.getElementsByClassName('vjs-control-bar')[0];
                controlBar.click(function(e) {
                    e.preventDefault();
                    // e.stopImmediatePropagation();
                });
            }
        });
    }

    $('.vjs-fullscreen-control.vjs-control.vjs-button').on('click touchstart', function() {
        $(".vjs-big-play-button").toggleClass('mobileTop');
    });

    document.addEventListener('fullscreenchange', (event) => {
        if (document.fullscreenElement) {
            $('.video-js .vjs-control-bar').addClass('in-full-screen');
        } else {
            $('.video-js .vjs-control-bar').removeClass('in-full-screen');
        }
    });

});


function addNewButton(data) {
    var controlBar,
        newElement = document.createElement('button'),
        newLink = document.createElement('span');

    newElement.id = data.id;
    newElement.className = 'vjs-control';
    newElement.appendChild(newLink);
    controlBar = document.getElementsByClassName('vjs-control-bar')[0];
    insertBeforeNode = document.getElementsByClassName('vjs-volume-panel')[0];
    controlBar.insertBefore(newElement, insertBeforeNode);

    return newElement;

}

function addNextVideoButton(nextVideoParam) {
    var nextVideo = addNewButton({
        id: "nextVideo"
    });
    nextVideo.onclick = function() {
        if (nextVideoParam != null) {
            window.location.href = nextVideoParam;
        }
    };
}

function gradiantVideo(backgroundImg) {
    var linearGradiant = "linear-gradient(to bottom,rgb(8 8 8) 0%, rgba(0, 0, 0, 0) 20%,rgba(0, 0, 0, 0) 80%, rgb(8 8 8) 100%)";
    document.getElementsByClassName('vjs-poster')[0].style.backgroundImage = linearGradiant + ',url(' + backgroundImg + ')';
}
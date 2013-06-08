    function disableCheckboxes(myValue , i)
                                {
                                        if(myValue != '')
                                        {
                                                document.getElementById('random_page_' + i).disabled = true;
                                                document.getElementById('random_post_' + i).disabled = true;
												document.getElementById('random_page_' + i).checked = false;
                                                document.getElementById('random_post_' + i).checked = false;
                                        }
                                        else
                                        {
                                                document.getElementById('random_page_' + i).disabled = false;
                                                document.getElementById('random_post_' + i).disabled = false;
                                        }
                                 }

                                function disableTextbox(i)
                                {
                                        var isCheckedRandomPage = document.getElementById('random_page_' + i).checked;
                                        var isCheckedRandomPost = document.getElementById('random_post_' + i).checked;
                                                if(isCheckedRandomPage === true || isCheckedRandomPost === true)
                                                        document.getElementById('single_pages_categories_' + i).disabled=true;
                                                 else
                                                        document.getElementById('single_pages_categories_' + i).disabled=false;
                                 }

                                function disableCheckboxesNew(myValue)
                                {
                                        if(myValue != '')
                                        {
                                                document.getElementById('random_page').disabled = true;
                                                document.getElementById('random_post').disabled = true;
                                        }
                                        else
                                        {
                                                document.getElementById('random_page').disabled = false;
                                                document.getElementById('random_post').disabled = false;
                                        }
                                 }

                                function disableTextboxNew()
                                {
                                        var isCheckedRandomPage = document.getElementById('random_page').checked;
                                        var isCheckedRandomPost = document.getElementById('random_post').checked;
                                                if(isCheckedRandomPage === true || isCheckedRandomPost === true)
                                                        document.getElementById('single_pages_categories').disabled=true;
                                                 else
                                                        document.getElementById('single_pages_categories').disabled=false;
                                 }

                                function alert_url(text)
                                {
                                        prompt("Use this url:",text);
                                }
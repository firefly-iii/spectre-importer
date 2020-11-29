<!--
  - DownloadStatus.vue
  - Copyright (c) 2020 james@firefly-iii.org
  -
  - This file is part of the Firefly III Spectre importer
  - (https://github.com/firefly-iii/spectre-importer).
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <https://www.gnu.org/licenses/>.
  -->

<template>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">Status window</div>
                <div class="card-body" v-if="'waiting_to_start' === this.status && false === this.triedToStart">
                    <p>
                        The tool is ready to download your transactions from Spectre. Please wait...
                    </p>
                </div>
                <div class="card-body" v-if="'waiting_to_start' === this.status && true === this.triedToStart">
                    <p>
                        The tool is ready to download your transactions from Spectre. Please wait...
                    </p>
                </div>
                <div class="card-body" v-if="'job_running' === this.status">
                    <p>
                        Download is in progress, please wait...
                    </p>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="100" aria-valuemin="0"
                             aria-valuemax="100" style="width: 100%"></div>
                    </div>
                    <download-messages
                        :messages="this.messages"
                        :warnings="this.warnings"
                        :errors="this.errors"
                    ></download-messages>
                </div>
                <div class="card-body" v-if="'job_done' === this.status ">
                    <p>
                        If any errors occurred, please read them below. If no errors or warnings appear
                        this page will redirect you in a moment.
                    </p>
                    <download-messages
                        :messages="this.messages"
                        :warnings="this.warnings"
                        :errors="this.errors"
                    ></download-messages>
                </div>
                <div class="card-body" v-if="'error' === this.status && true === this.triedToStart">
                    <p class="text-danger">
                        The job could not be started or failed due to an error. Please check the log files. Sorry about this :(.
                    </p>
                    <download-messages
                        :messages="this.messages"
                        :warnings="this.warnings"
                        :errors="this.errors"
                    ></download-messages>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "DownloadStatus",
        /*
    * The component's data.
    */
        data() {
            return {
                triedToStart: false,
                status: '',
                messages: [],
                warnings: [],
                errors: [],
                downloadUrl: window.configDownloadUrl,
                flushUrl: window.flushUrl
            };
        },
        props: [],
        mounted() {
            console.log(`Mounted, check job at ${downloadStatusUrl}.`);
            this.getJobStatus();
            this.callStart();
        },
        methods: {
            getJobStatus: function () {
                console.log('getJobStatus');
                axios.get(downloadStatusUrl).then((response) => {
                    // handle success
                    this.status = response.data.status;
                    this.errors = response.data.errors;
                    this.warnings = response.data.warnings;
                    this.messages = response.data.messages;
                    console.log(`Job status is ${this.status}.`);
                    if (false === this.triedToStart && 'waiting_to_start' === this.status) {
                        // call to job start.
                        console.log('Job hasn\'t started yet. Show user some info');
                        return;
                    }
                    if (true === this.triedToStart && 'waiting_to_start' === this.status) {
                        console.log('Job hasn\'t started yet.');
                    }
                    if ('job_done' === this.status) {
                        console.log('Job is done!');
                        if (
                            this.warnings.length === 0 &&
                            this.errors.length === 0
                        ) {
                            window.location = mappingUrl;
                            return;
                        }
                        return;
                    }

                    setTimeout(function () {
                        console.log('Fired on setTimeout');
                        this.getJobStatus();
                    }.bind(this), 1000);
                });
            },
            callStart: function () {
                console.log('Call job start URL: ' + downloadStartUrl);
                axios.post(downloadStartUrl).then((response) => {
                    this.getJobStatus();
                }).catch((error) => {
                    this.status = 'error';
                });
                this.getJobStatus();
                this.triedToStart = true;
            },
        },
        watch: {}
    }
</script>

<style scoped>

</style>

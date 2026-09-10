 import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-sterilization',
  templateUrl: './sterilization.component.html',
  styleUrls: ['./sterilization.component.css'],
})
export class SterilizationComponent implements OnInit {
  results;
  lafs;
  balances;
  isNew = false;
  medias;
  selectedBatch = [];
  batches;
  qty_taken: number;
  flag1 = false;
  flag2 = false;
  flag3 = false;
  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getMedia();
    this.getAutoclaves();
  }

  getAutoclaves() {
    this.service
      .get('equipments.php?type=getAutoclaves')
      .subscribe((response) => {
        this.equipments = response;
      });
  }

  equipments;

  getMedia() {
    this.service
      .get('microbiology/media.php?type=getMediaPrepartionForSterActivity')
      .subscribe((response) => {
        this.results = response;
      });
  }

  selectedResult = [];

  View(index) {
    this.selectedResult = this.results[index];

    this.isNew = true;
  }
  View1(index) {
    this.selectedResult = this.results[index];

    this.isNew1 = true;
  }

  isNew1 = false;

  ASstartTime = '';
  ASendTime = '';

  openendtime(value) {
    var d = new Date(),
      year = d.getFullYear(),
      month = (d.getMonth() + 1 < 10 ? '0' : '') + (d.getMonth() + 1),
      day = (d.getDate() < 10 ? '0' : '') + d.getDate(),
      h = (d.getHours() < 10 ? '0' : '') + d.getHours(),
      m = (d.getMinutes() < 10 ? '0' : '') + d.getMinutes();
    if (value == 'START') {
      this.ASstartTime = h + ':' + m;
    } else if (value == 'END') {
      this.ASendTime = h + ':' + m;
    }
  }

  download() {
    
  };

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service
      .post(
        'microbiology/media.php?type=saveSterStartActivity&id=' +
          this.selectedResult['id'],
        JSON.stringify(data.value)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.getMedia();
          alertify.success('Record Inserted successfully');
          this.isNew = false;
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
  }
  save1(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service
      .post(
        'microbiology/media.php?type=saveSterEndActivity&id=' +
          this.selectedResult['id'],
        JSON.stringify(data.value)
      )
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.getMedia();
          alertify.success('Record Inserted successfully');
          this.isNew1 = false;
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
  }
}

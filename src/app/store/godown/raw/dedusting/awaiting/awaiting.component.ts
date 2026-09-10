import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  isView = false;

  isAuto = true;
  isStart = false;
  isEnd = false;
  isMannual = true;

  labors;
  equipments;

  from_time;
  to_time;
  area_cleaned_from;
  area_cleaned_to;
  equip_cleaned_from;
  equip_cleaned_to;

  results;
  selectedReport = [];
  constructor(private service: DataAccessService) {
  }

  ngOnInit() {
    this.getPendingDedustingMaterials();
    this.getApprovedLabors();
    this.getSelectedEquipments();
  }

  getPendingDedustingMaterials() {
    this.service.get('store/raw.php?type=getPendingDedustingMaterials').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
    let date = new Date();
    this.from_time = date.getUTCFullYear() + "-" + this.twoDigits(1 + date.getUTCMonth()) + "-" + this.twoDigits(date.getUTCDate()) + " " + this.twoDigits(date.getUTCHours()) + ":" + this.twoDigits(date.getUTCMinutes()) + ":" + this.twoDigits(date.getUTCSeconds());
  }

  getApprovedLabors() {
    this.service.get('common.php?type=getLabours').subscribe(response => {
      this.labors = response;
    });
  } 

  getSelectedEquipments() {
    this.service.get('equipments.php?type=getStoreVacuums').subscribe(response => {
      this.equipments = response;
    });
  }

  saveMaterialDedusting(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedReport['id'];
    this.service.post('store/raw.php?type=saveMaterialDedusting', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Dedusting record saved Successfully');
        data.resetForm();
        this.isView = false;
        this.getPendingDedustingMaterials();
      } else {
        alertify.error('An error occured');
      }
    });
  }

  twoDigits(d) {
    if(0 <= d && d < 10) return "0" + d.toString();
    if(-10 < d && d < 0) return "-0" + (-1*d).toString();
    return d.toString();
  }

  startAuto() {
    this.isMannual = false;
    this.isStart = true;
    this.isAuto = true;
  }

  startDedusting() {
    let date = new Date();
    this.from_time = date.getUTCFullYear() + "-" + this.twoDigits(1 + date.getUTCMonth()) + "-" + this.twoDigits(date.getUTCDate()) + " " + this.twoDigits(date.getUTCHours()) + ":" + this.twoDigits(date.getUTCMinutes()) + ":" + this.twoDigits(date.getUTCSeconds());
    this.isStart = false;
    this.isEnd = true;
  }

  endDedusting() {
    let date = new Date();
    this.to_time = date.getUTCFullYear() + "-" + this.twoDigits(1 + date.getUTCMonth()) + "-" + this.twoDigits(date.getUTCDate()) + " " + this.twoDigits(date.getUTCHours()) + ":" + this.twoDigits(date.getUTCMinutes()) + ":" + this.twoDigits(date.getUTCSeconds());
    this.isEnd = false;  
  }

  getCurrentTime(action, value) {
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    /* let time = new Date().toLocaleTimeString(); */
    if (value == "usages") {
      if (action == 'from_time') {
        this.from_time = h + ':' + m;
      } else {
        this.to_time = h + ':' + m;
      }
    } else if (value =="area_clean") {
      if (action == 'from_time') {
        this.area_cleaned_from = h + ':' + m;
      } else {
        this.area_cleaned_to = h + ':' + m;
      }
    } else if (value =="equip_clean") {
      if (action == 'from_time') {
        this.equip_cleaned_from = h + ':' + m;
      } else {
        this.equip_cleaned_to = h + ':' + m;
      }
    }
  }

}

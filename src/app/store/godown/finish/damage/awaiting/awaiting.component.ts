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
  results;

  selectedReport = [];
  total = 0;
  damages = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingDamages();
  }

  getPendingDamages() {
    this.service.get('store/raw.php?type=getPendingDamages').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
    this.total = +this.selectedReport['receiving_details'].outer_damage + +this.selectedReport['receiving_details'].inner_damage;
    let z = 1;
    for (let i = 0; i < +this.selectedReport['receiving_details'].outer_damage; i++) {
      let temp = {};
      temp['container_no'] = z;
      temp["status"] = "Outer Damage";
      temp['remark'] = "";
      this.damages[this.damages.length] = temp;
      z++;
    }

    for (let i = 0; i < +this.selectedReport['receiving_details'].inner_damage; i++) {
      let temp = {};
      temp['container_no'] = z;
      temp["status"] = "Inner Damage";
      temp['remark'] = "";
      this.damages[this.damages.length] = temp;
      z++;
    }
  }

  save() {
    let temp = {};
    temp['containers'] = this.damages;
    temp['total_damage'] = this.total;
    this.service.post('store/raw.php?type=saveDamageInspection&id=' + this.selectedReport['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Damage Container Inspection form send for QA Approval');
        this.isView = false;
        this.getPendingDamages();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}

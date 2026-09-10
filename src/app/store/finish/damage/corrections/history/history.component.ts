import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-history',
  templateUrl: './history.component.html',
  styleUrls: ['./history.component.css']
})
export class HistoryComponent implements OnInit {

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
}

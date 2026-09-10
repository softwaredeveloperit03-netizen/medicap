import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-correction',
  templateUrl: './correction.component.html',
  styleUrls: ['./correction.component.css']
})
export class CorrectionComponent implements OnInit {

  isView = false;
  results;
  selectedReport = [];
  damage;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingGRN();
  }

  getPendingGRN() {
    this.service.get('store/raw.php?type=getPendingGRN').subscribe(response => {
      this.results = response;
    });
  }

  viewResult(index) {
    this.selectedReport = this.results[index];
    this.damage = this.selectedReport['grn_details'];
    console.log(this.damage)
    this.isView = true;
  }

}

import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dispense',
  templateUrl: './dispense.component.html',
  styleUrls: ['./dispense.component.css']
})
export class DispenseComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingDispensing();
  }

  getPendingDispensing() {
    this.service.get('store/dispensing/packing.php?type=getPendingDispensing').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  saveLineClearance(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('store/dispensing/packing.php?type=saveDispensingForm&id=' + this.selectedResult['id'] + '&bmr_no=' + this.selectedResult['bmr_no'], JSON.stringify(this.selectedResult['clearances'])).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record saved successfully');
        this.isView = false;
        this.getPendingDispensing();
      }
    });
  }

  saveDispensingForm(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    this.service.post('store/dispensing/packing.php?type=saveDispensingForm&id=' + this.selectedResult['id'] + '&bmr_no=' + this.selectedResult['bmr_no'], JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record saved successfully');
        this.isView = false;
        this.getPendingDispensing();
      }
    });
  }

}

import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getActiveDispensingForm();
  }

  getActiveDispensingForm() {
    this.service.get('store/dispensing.php?type=getActiveDispensingForm').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.post('store/dispensing.php?type=checkDispensingForm&id=' + this.selectedResult['id'] + '&status=' + status, JSON.stringify(this.selectedResult['data'])).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getActiveDispensingForm();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}

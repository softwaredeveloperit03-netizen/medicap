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

  selectedDev = [];
  remark = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingDeviations();
  }

  getPendingDeviations() {
    this.service.get('deviation.php?type=getPendingDeviations').subscribe(response => {
      this.results = response;
    });
  }

  viewDeviation(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }

  update(value) {
    this.service.get('deviation.php?type=checkDeviation&status=' + value + '&id=' + this.selectedDev['id'] + '&remark=' + this.remark).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Deviation Updated Successfully');
        this.isView = false;
        this.remark = '';
        this.getPendingDeviations();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}

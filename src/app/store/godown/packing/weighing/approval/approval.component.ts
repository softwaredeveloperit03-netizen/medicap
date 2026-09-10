import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  results;

  selectedReport = [];

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getCheckingWeighingMaterials();
  }

  getCheckingWeighingMaterials() {
    this.service.get('store/packing.php?type=getCheckingWeighingMaterials').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.post('store/packing.php?type=updateWeighing&id=' + this.selectedReport['id'] + '&status=' + status, JSON.stringify(this.selectedReport['weighing_details'])).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Weighing Record Updated Successfully');
        this.isView = false;
        this.getCheckingWeighingMaterials();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }


}

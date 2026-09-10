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

  selectedReport = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getCheckingWeighingMaterials();
  }

  getCheckingWeighingMaterials() {
    this.service.get('store/raw.php?type=getCheckingWeighingMaterials').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.post('store/raw.php?type=updateWeighing&id=' + this.selectedReport['id'] + '&status=' + status, JSON.stringify(this.selectedReport['weighing_details'])).subscribe(response => {
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

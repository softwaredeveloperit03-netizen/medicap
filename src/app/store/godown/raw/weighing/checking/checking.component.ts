import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {
  no=100;
  isView = false;
  results;
  weighing_remark;
  isProceed = false;
  batches = [];
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

  viewDetails(index){
    let bat = this.selectedReport['batches'];
    this.batches = bat[index];
    console.log('btches' ,this.batches);
    this.isProceed = true;
  }

  update(status) {
    this.service.post('store/raw.php?type=updateWeighing&id=' + this.selectedReport['id'] + '&status=' + status+'&weighing_remark='+this.weighing_remark, JSON.stringify(this.selectedReport['weighing_details'])).subscribe(response => {
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

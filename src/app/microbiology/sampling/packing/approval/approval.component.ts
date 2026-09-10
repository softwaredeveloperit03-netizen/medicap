import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isNew = false;
  results;

  selectedSampling = [];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getCheckedSamplings();
  }

  getCheckedSamplings(){
    this.service.get('qc/sampling/packing.php?type=getCheckedSamplings').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedSampling = this.results[index];
    this.isNew = true;
  }

  upgateSample(status){
    let temp = {};
    temp["sampling_no"] = this.selectedSampling['sampling_no'];
    temp["grn_no"] = this.selectedSampling['grn_no'];
    temp["specification_no"] = this.selectedSampling['specification_no'];
    temp["grn_no"] = this.selectedSampling['grn_no'];
    temp["material_code"] = this.selectedSampling['material_code'];
    this.service.post('qc/sampling/packing.php?type=updateCheckedSampling&status=' + status + '&id=' + this.selectedSampling['id'], JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Updated Successfully!');
        this.isNew = false;
        this.getCheckedSamplings();
      }else{
        alertify.error('Field an error occured,Please try Again!');
      }
    });
  }

}

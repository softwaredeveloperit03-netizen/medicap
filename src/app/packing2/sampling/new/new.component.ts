import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  results;

  from_date = '';
  to_date = '';
  max_date = '';
  company_unit='';
  product_type = '';
  isView=false;
  selectedResult=[];
  gross_wt=0;
  tare_wt=0;
  gross_total = 0;
  tare_total = 0;
  net_total = 0;
  sample_qty=0;
  containers=[];
  constructor(private service: DataAccessService) {
  }

  ngOnInit() {
    this.getCompletedBatches();
  }

  getCompletedBatches() {
    this.service.get('packing/sampling.php?type=getPendingFGSampling').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  send(){
    this.service.post('packing/sampling.php?type=sendIntimation',JSON.stringify(this.selectedResult)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('send Intimation Successfuly');
        this.getCompletedBatches();
        this.isView=false;
      }else{
        alertify.error('Some error Ocuured!');
      }

    });
  }

}

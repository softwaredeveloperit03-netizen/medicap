import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  isView = false;
  results;

  selectedReport = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingRetests();
  }
  
  getPendingRetests() {
    this.service.get('store/raw.php?type=getPendingRetests').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedReport = this.results[index];
    this.isView = true;
    this.containers=Number(Math.round(this.selectedReport['qty']/this.selectedReport['pack_size']));
  }


  containers =0;

  save(){

    if(this.containers <=0 ){
      alertify.error('Enter Container No.');
      return false;
    }
  
    let temp = this.selectedReport;
    temp['containers'] = this.containers;
    this.service.post('qc/sampling.php?type=saveRetestSamplingRequest',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
       alertify.success('Data updated Successfully!');
        this.isView = false;
        this.getPendingRetests();
      }else{
       alertify.error('Failed an error occurd,Please try again!');
      }
    });
  }

}

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
  results: any = [];
  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getAwaitingBatches();
  }

  getAwaitingBatches() {
    this.service.get('production/plant9/transfer.php?type=getAwaitingBatches').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  calculate() {
    this.selectedResult['batch_container'] = Math.ceil(this.selectedResult['dispatch_qty'] / this.selectedResult['pack_size']);
  }
  save(data){
  if(this.selectedResult.length == 0){
    alertify.error("All Fields Are Required !!");
    return;
  }
    this.service.post('production/plant9/transfer.php?type=transferMaterial',JSON.stringify(this.selectedResult)).subscribe(response =>{
      if(response['status'] == 'success'){
        this.getAwaitingBatches();
        this.isView = false;
        alertify.success("Record Save Successfully !!");
      }else{
        alertify.error("Error to Save !!");
      }
    });
  }
}

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
    results: any = [];
    selectedResult = [];
    constructor(private service: DataAccessService) { }
  
    ngOnInit(): void {
      this.getAwaitingBatches();
    }
  
    getAwaitingBatches() {
      this.service.get('production/plant9/transfer.php?type=getPendingBatchRelease').subscribe(response => {
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
    save(status){
      this.service.get('production/plant9/transfer.php?type=checkBatchRelease&id='+this.selectedResult['id']+'&status='+status).subscribe(response =>{
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
  
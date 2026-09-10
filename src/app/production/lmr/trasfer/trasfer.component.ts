import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-trasfer',
  templateUrl: './trasfer.component.html',
  styleUrls: ['./trasfer.component.css'],
  providers:[DatePipe]
})
export class TrasferComponent implements OnInit {

  results;

  from_date = '';
  to_date = '';
  max_date = '';

  product_type = '';
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    let date = new Date();
    this.from_date = this.datePipe.transform(date, 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(date, 'yyyy-MM-dd');
    this.max_date = this.datePipe.transform(date, 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getCompletedBatches();
  }

  getCompletedBatches() {
    this.service.get('production/lot/sampling.php?type=getAwaitingTransferMaterials').subscribe(response => {
      this.results = response;
    });
  }

  transfer(index,status){
    let selectedResult=this.results[index];

    this.service.post('production/lot/sampling.php?type=transferMaterial&status='+ status,JSON.stringify(selectedResult)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Transfer Material Successfuly');
        this.getCompletedBatches();
      }else{
        alertify.error('some error occured!');
      }
    });
  }

}

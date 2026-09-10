import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-transfer',
  templateUrl: './transfer.component.html',
  styleUrls: ['./transfer.component.css'],
  providers:[DatePipe]
})
export class TransferComponent implements OnInit {
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
    this.service.get('production/bmr/sampling.php?type=getAwaitingTransferMaterials').subscribe(response => {
      this.results = response;
    });
  }


  transfer(index){
    let selectedResult=this.results[index];

    this.service.post('production/bmr/sampling.php?type=transferMaterial',JSON.stringify(selectedResult)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('Transfer Material Successfuly');
        this.getCompletedBatches();
      }else{
        alertify.error('some error occured!');
      }
    });
  }

}

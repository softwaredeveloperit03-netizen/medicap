import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
  declare let alertify;
@Component({
  selector: 'app-water-investigation',
  templateUrl: './water-investigation.component.html',
  styleUrls: ['./water-investigation.component.css'],
  providers:[DatePipe] 
})
export class WaterInvestigationComponent implements OnInit {

  from_date = '';
  to_date = '';
  results;
  lafs;
  balances;

  constructor(private service : DataAccessService,private datePipe :DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }

  ngOnInit(): void {
    this.em_investigation();
  }

  em_investigation(){
    this.service.get('microbiology/waterInvestigation.php?type=getWaterInvestigation&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
      this.results = response;
    });
  }
  
  download(){
    this.service.open('microbiology/waterInvestigation.php?type=downloadWaterInvestigation&from_date='+this.from_date+'&to_date='+this.to_date)
  }
  save(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('microbiology/waterInvestigation.php?type=saveWaterInvestigation',JSON.stringify (data.value)).subscribe(response =>{
      if (response['status'] === 'success') {
        this.em_investigation();
        alertify.success('Record Inserted successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }
  


}

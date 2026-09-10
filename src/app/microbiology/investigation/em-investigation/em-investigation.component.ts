import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
  declare let alertify;
@Component({
  selector: 'app-em-investigation',
  templateUrl: './em-investigation.component.html',
  styleUrls: ['./em-investigation.component.css'],
  providers:[DatePipe] 
})
export class EmInvestigationComponent implements OnInit {
   
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
      this.service.get('microbiology/emInvestigation.php?type=getEmInvestigation&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
        this.results = response;
      });
    }
    
    download(){
      this.service.open('microbiology/emInvestigation.php?type=downloadEmInvestigation&from_date='+this.from_date+'&to_date='+this.to_date)
    }
    save(data){
      if (!data.valid) {
        alertify.error('All fields are required');
        return;
      }
      this.service.post('microbiology/emInvestigation.php?type=saveEm',JSON.stringify (data.value)).subscribe(response =>{
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
  
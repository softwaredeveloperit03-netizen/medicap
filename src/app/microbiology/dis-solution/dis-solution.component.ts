import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dis-solution',
  templateUrl: './dis-solution.component.html',
  styleUrls: ['./dis-solution.component.css'],
  providers:[DatePipe]
})
export class DisSolutionComponent implements OnInit {

  from_date = '';
  to_date = '';
  results;
  labours;
  lafs;
  balances;

  constructor(private service : DataAccessService,private datePipe :DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }

  ngOnInit(): void {
    this.getAutoclave();
    this.getLabours();
  }
  getLabours(){
    this.service.get('common.php?type=getOperators').subscribe(response =>{
      this.labours = response;
    });
  }
 
  getAutoclave(){
    this.service.get('microbiology/disinfect_solution.php?type=getDisinfect&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
      this.results = response;
    });
  }
  
  download(){
    this.service.open('microbiology/disinfect_solution.php?type=downloadDisinfect&from_date='+this.from_date+'&to_date='+this.to_date)
  }
  save(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('microbiology/disinfect_solution.php?type=saveDisinfect',JSON.stringify (data.value)).subscribe(response =>{
      if (response['status'] === 'success') {
        this.getAutoclave();
        alertify.success('Record Inserted successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }
  



}

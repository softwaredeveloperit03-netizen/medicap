  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
  import { DatePipe } from '@angular/common';
  declare let alertify;
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {
    from_date = '';
    to_date = '';
    today = '';
    results;
    plant_names;
      constructor(private service : DataAccessService,private datePipe :DatePipe) {
        this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
        this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
        this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
       }
    
      ngOnInit(): void {
        this.service.observablePlant.subscribe(response =>{
          this.plant_names = response;
        });
        this.changeFilter();
      }
    
      changeFilter(){
        this.service.get('engineering/aircompressor.php?type=getFilterReplacement&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
          this.results = response;
        });
      }
     
      download(){
        this.service.open('engineering/aircompressor.php?type=downloadFilterReplacement&from_date='+this.from_date+'&to_date='+this.to_date)
      }
      saveOperation(data){
        if (!data.valid) {
          alertify.error('All fields are required');
          return;
        }
        this.service.post('engineering/aircompressor.php?type=saveFilterReplacement',JSON.stringify (data.value)).subscribe(response =>{
          if (response['status'] === 'success') {
            this.changeFilter();
            alertify.success('Record Inserted successfully');
            data.resetForm();
          } else {
            alertify.error(response['status']);
          }
        });
      }
    }
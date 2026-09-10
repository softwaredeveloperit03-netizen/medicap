import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
  import { DatePipe } from '@angular/common';
  declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'schedule', title: 'Schedule', route: '/engineering/aircompressor/filter/schedule', icon: 'fa-calendar-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'process', title: 'Replacement Process', route: '/engineering/aircompressor/filter/process', icon: 'fa-cogs', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'log', title: 'Replacement Records', route: '/engineering/aircompressor/filter/log', icon: 'fa-clipboard', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
  ];

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
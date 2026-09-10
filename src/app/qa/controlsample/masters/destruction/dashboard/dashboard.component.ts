import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  results;
  isEdit=false;
  selectedResult = [];
  isView = false;
  
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
   this.getFreuency();
  }

  getFreuency() {
    this.service.get('master/destruction.php?type=getDestruction').subscribe(response => {
      this.results = response;
    });
  }

  edit(index){
    this.selectedResult=this.results[index];
    this.isEdit=true;
  }

  editFrequency(){
     this.service.post('master/destruction.php?type=updateDestruction&id='+this.selectedResult["id"],JSON.stringify(this.selectedResult)).subscribe(response=>{
        if(response['status']=='success'){
          alertify.success('Updates Successuly');
          this.isEdit=false;
          this.getFreuency();
        }else{
          alertify.error('some error occured');
        }
      });
    }




}

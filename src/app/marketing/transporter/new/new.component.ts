import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  state;
  selectedstate=[];
  isDomastic = false;

  agents;
  refered_by = 'Direct Customer';
  client_type = 'Distributor';
  order_category = "DOMESTIC";
  gst_type = 'IGST';

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getStates();
  }

  getStates(){
    this.service.get('common.php?type=getStates').subscribe(response=>{
      this.state=response;
    });
  }

  getStateData(index){
    index=index-1;
    if(index !== -1){
      this.selectedstate=this.state[index];
    }
  }
  save(data){
    if(data.valid)
    this.service.post('marketing/transporter.php?type=saveTransporter',JSON.stringify(data.value)).subscribe(response=>{
      alert("save successfully");
      data.reset();
      this.router.navigate(['/marketing/transporter']);
    });else{
      alert("all fields are required")
    }

  }

}

import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  results;
  selectresult=[];
  isView=false;
  instructions=[];

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
   this.getInstructions()
  }
  getInstructions(){
    this.service.get('bmr/instruction.php?type=getInstructions').subscribe(response=>{
      this.results=response;
    });
  }
  view(index){
    this.selectresult = this.results[index];
    this.isView = true;
  }
  add(data){
    if(!data.valid){
      alert("all field required");
      return;
    }
    this.instructions[this.instructions.length]=data.value;
    data.reset();
  }
  del(index){
    this.instructions.splice(index,1)
  }
  save(){ 
    let temp={};
    temp['dosage_form']=this.selectresult['dosage_form'];
    temp['instructions']=this.instructions;
    this.service.post('bmr/instruction.php?type=saveInstruction',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']='success'){
        alert("saved successfully")
        this.getInstructions();
        this.isView=false;
      }else{
        alert("error occured")
      }
    });

  }


}

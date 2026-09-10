import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-client-new',
  templateUrl: './client-new.component.html',
  styleUrls: ['./client-new.component.css']
})
export class ClientNewComponent implements OnInit {
  isDomastic = false;

  agents;
  refered_by = 'Direct Customer';
  client_type = 'Distributor';
  order_category = "DOMESTIC";
  gst_type = 'IGST';

  branches = [];
  divisions = [];
  selected=[];
  state;
  selectedstate=[];
  states;
  isBranch = false;
  areas;
  cities;
  areas_name;
  countries;
country: any;
isDiv=false
devision_for: any;
  constructor(private service: DataAccessService,private router: Router) { }

  ngOnInit() {
    this.getAgents();
    this.getCountries();
    this.getArea();
    this.getStates();
  }
  selectedFile2: File;


  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
  }

  getAgents(){
    this.service.get('marketing/agent.php?type=getApprovedAgents').subscribe(response=>{
      this.agents=response;
    })
  }

  getStates(){
    this.service.get('common.php?type=getStates').subscribe(response => {
      this.state= response;
    })
  }

  getState(value){
    this.service.get('master/state.php?type=getStateBycountry&country='+value).subscribe(response => {
      this.states= response;
    })
  }


  getArea(){
    this.service.get('master/area.php?type=getArea').subscribe(response => {
      this.areas= response;
    })
  }


  getCity(value){
    this.service.get('master/area.php?type=getCityByStateName&state_name='+value).subscribe(response => {
      this.cities= response;
    })
  }



  getAreaName(value){
    this.service.get('master/area.php?type=getAreaByCity&city='+value).subscribe(response => {
      this.areas_name= response;
    })
  }


  getCountries(){
    this.service.get('master/country.php?type=getCountries').subscribe(response => {
      this.countries= response;
    })
  }

  getPercentage(index){
    if(index!==-1){  
      this.selected=this.agents[index];
    }
  }
  act(){
    this.isBranch=true;
  }


  submit(data) {
    // if (!data.valid) {
    //   alert('All fields are required');
    //   return;
    // }
    
   const uploadData = new FormData();
    let temp = data.value;
    if (this.selectedFile2 !== undefined) {
      uploadData.append('photo', this.selectedFile2, this.selectedFile2.name);
    }

  
    temp['branch'] = this.branches;
    temp['from'] = 'marketing';
    temp['comType'] = 'Client';
    

     uploadData.append('data', JSON.stringify(temp));
    this.service.post('marketing/client.php?type=saveClient', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record Inserted Successfully');
        data.resetForm();
        this.branches=[];
        this.router.navigate(['/marketing/clients']);
      } else {
        alert('Please try Again');
      }
    });
  }





  add(data) {
    // if (!data.valid) {
    //   alert('All fields are required');
    //   return;
    // }
    if (data.value.gst_type == 'Exempted' || data.value.gst_type == 'Non Registered') {
      data.value.gst_no = 'NA';
    }
    let temp=data.value;
    temp['state_name']=this.selectedstate['state_name'];
    this.branches[this.branches.length] = temp;
    data.resetForm();
    this.isBranch = false;
  }




  addDivision(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.divisions[this.divisions.length] = data.value;
    data.resetForm();
  }

  delDivision(index) {
    this.divisions.splice(index, 1);
  }


}

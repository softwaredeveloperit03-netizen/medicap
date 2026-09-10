import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;

@Component({
  selector: 'app-newform',
  templateUrl: './newform.component.html',
  styleUrls: ['./newform.component.css']
})
export class NewformComponent implements OnInit {

  address;
  sample_address;
  requirement;
  mobile_no;
  country;
  unit_name;
  name_client;
  temp_name;
  temp_address;
  temp_country;
  temp_state;
  temp_Ocountry;
  temp_no;
  temp_email;
  stp;
  vendor_coa;
  enter_stp;
  units;
  grades;
  clients;
  selectedFile1: File;
  selectedFile2: File;

  solvent_system;
  enter;
client_code: any;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    // this.service.observableGrade.subscribe(response => {
    //   this.grades = response;
    // });
    this.getGrades();
    this.getClients();
    this.getUnits(); 
  }

  checkAddress(value) {
    if (value) {
      this.temp_name = this.name_client;
      this.temp_address = this.address;
    } else {
      this.temp_name = '';
      this.temp_address = '';
      this.temp_country = '';
      this.temp_state = '';
      this.temp_Ocountry = '';
      this.temp_no = '';
      this.temp_email = '';
    }
  }

  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    const uploadData = new FormData();
    let temp = data.value;
     
     this.service.post('/hr/achievement.php?type=saveSample', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.router.navigate(['/marketing/sample/checking']);
      } else {
        console.log(response);
        alert('Failed: An error occured');
      }
    });
  }

  
  onFileChanged1(event) {
    this.selectedFile1 = event.target.files[0];
  }
  onFileChanged2(event) {
    this.selectedFile2 = event.target.files[0];
  }

  getGrades(){
    this.grades =[];
    this.service.get('master/product.php?type=getGrades').subscribe(response => {
      this.grades = response;
    
      this.service.observableGrade.subscribe(response => {
        this.grades = response;
      });
    
    })
  }
  
  getClients() {
    this.service.get('common.php?type=getClients').subscribe(response => {
      this.clients = response;
    });
  }

  getUnits() {
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
  }
}



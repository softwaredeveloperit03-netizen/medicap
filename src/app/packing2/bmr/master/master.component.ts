import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-master',
  templateUrl: './master.component.html',
  styleUrls: ['./master.component.css']
})
export class MasterComponent implements OnInit {

  processes=[];
  dosages;
  dosage_form;
  process_type;
  results;
  constructor(private service: DataAccessService,private router:Router) { }
  ngOnInit() {
    this.getDosages();
  }


  // getDosages() {
  //   this.service.get('production/stage.php?type=getDosage').subscribe(response => {
  //     this.dosages = response;
  //   });
  // }
  getDosages() {
    this.service.get('master/product.php?type=get_dosage_types').subscribe(response => {
      this.dosages = response;
    });
  }


  addnewProcess(data){
    if (!data.value) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.processes[this.processes.length] = temp;
    data.resetForm();
  }
  getstage_master(){
    this.service.get('production/stage.php?type=getstage_master').subscribe(response=>{
      this.results=response;
    });
  }

  saveProcess() {
    for (let i = 0; i < this.processes.length; i++) {
      this.processes[i].dosage_form = this.dosage_form;
      this.processes[i].process_type = this.process_type;
    }
    this.service.post('production/stage.php?type=savestage_master&process_type='+this.process_type+'&dosage_form='+this.dosage_form, JSON.stringify(this.processes)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Manufacturing Processes Saved Successfully');
        this.router.navigate(['/packing/bmr/log']);
        this.dosage_form = '';
        this.processes = [];
      } else {
        alert(response['status']);
      }
    });
  }



}

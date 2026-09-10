import { Component, OnInit } from '@angular/core';
import { Router, Routes } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  dosages;

  isBlister=false;
  isBottel=false;
  isStrip=false;
  isyes=false;
  isNo=false;
  isyesouter=false;
  isNoouter=false;
  constructor(private service:DataAccessService,private router: Router) { }

  ngOnInit() {
    this.getDosages();
  }

  getDosages(){
    this.service.get('common.php?type=getDosages').subscribe(response=>{
      this.dosages = response;
    });
  }
  
  getSubtype(data){
    if(data=='Blister'){
      this.isBlister=true;
      this.isBottel=false;
      this.isStrip=false;
    }else if(data=='Bottle'){
      this.isBlister=false;
      this.isBottel=true;
      this.isStrip=false;
    }else if(data=='Strip'){
      this.isStrip=true;
      this.isBlister=false;
      this.isBottel=false;
    }
  }
  getMono(data){
    if(data=='yes'){
      this.isyes=true;
      this.isNo=false;
    }else if(data =='no'){
      this.isyes=false;
      this.isNo=true;
    }
  }
  getOuter(show){
    if(show =='yes'){
      this.isyesouter=true;
      this.isNoouter=false;
    }else if(show =='no'){
      this.isyesouter=false;
      this.isNoouter=true;
    }
  }

  save(data){
    if(!data.valid){
      alert('all feilds are required');
      return;
    }
    let temp=data.value;
    this.service.post('packing/configuration.php?type=saveConfiguration',JSON.stringify(temp)).subscribe(response=>{
      if(response['status'] ==='success') {
        alert('data save successfuly');
        data.resetForm();
       this.router.navigate(['/configuration']);
      }else{
        alert('some error occured');
      }
    });
  }

}

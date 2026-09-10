import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-leadquaot',
  templateUrl: './leadquaot.component.html',
  styleUrls: ['./leadquaot.component.css']
})
export class LeadquaotComponent implements OnInit {
  enquirylist;
  selectedResult = [];
  selectedCountry='';
  isView = false;
  clients;
  company=''
  lists;
  materials=[];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getenquirylist();
    this.getclientlist();
  }


  getclientlist() {
    this.service.get('marketing/lead.php?type=getClientsLog').subscribe((response: any) => {
      this.clients = response;
      // console.log('rd',this.clients);
    });
  }


  getenquirylist(){
    this.service.get('marketing/lead.php?type=getLeadsQuatation').subscribe((response : any) => {
      this.enquirylist = response;
      this.filterMaterial();
     })
  }

  view(i){
    this.selectedResult = this.enquirylist[i];
    this.isView = true;
  }

  filterMaterial() {
    this.materials = [];
    for (let i = 0; i < this.enquirylist.length; i++) {
      let material = this.enquirylist[i];
      if (material['company'].toUpperCase().includes(this.company.toUpperCase())) {
        this.materials[this.materials.length] = material;
      }
    }
  }
  
} 

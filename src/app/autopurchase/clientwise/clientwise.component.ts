import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-clientwise',
  templateUrl: './clientwise.component.html',
  styleUrls: ['./clientwise.component.css'],
  providers: [DatePipe],
})
export class ClientwiseComponent implements OnInit {
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPOsLog(); 
  }

  result;
  getPOsLog(){
    this.service.get('marketing/po.php?type=getPOsLogForClientWise').subscribe(response =>{
      this.result =response;
       this.groupOrdersByGroupCode();
    });
  }

  groupedOrders: any[] = [];

groupOrdersByGroupCode() {
  const groups: any = {};

  this.result.forEach(order => {
    const code = order.groupcode || 'NoGroup';

    if (!groups[code]) {
      groups[code] = {
        groupcode: code,
        mainGroupName: order.mainGroupName || 'No Group',
        orders: []   // parent array to store all orders for this group
      };
    }

    groups[code].orders.push(order);
  });

  // Convert object to array
  this.groupedOrders = Object.values(groups);
}


 
  isView = false;
  selectedResult = [];

  view(data){
    this.selectedResult = data;
    this.isView = true;
  }
    selectedPOs=[]
  onCheckboxChange(po: any) {
  if (po.selected) {
    // Add if checked and not already present
    if (!this.selectedPOs.includes(po)) {
      this.selectedPOs.push(po);
    }
  } else {
    // Remove if unchecked
    this.selectedPOs = this.selectedPOs.filter(item => item !== po);
  }

  console.log("Selected items:", this.selectedPOs);
}

  

 
  downloadpo(doc_url) {
    doc_url = this.service.url + '../../upload/poentry/' + doc_url;
    window.open(doc_url, '_blank');
  }



  
   searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.result; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.result.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entryOn') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }


  send(){
    let temp={};
    temp['selectedPOs']=this.selectedPOs;
      this.service.post('purchase/autopurchase.php?type=sendOrdersForShotages', JSON.stringify(temp)).subscribe(response => {
            if (response['status'] == 'success') {
              this.selectedPOs = [];
              this.getPOsLog(); 
            } else {
              alertify.error('Failed: An error occured, please try again!');
            }
          });
  }
 
   
}



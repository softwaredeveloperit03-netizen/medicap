import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe],
})
export class LogComponent implements OnInit {
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPOsLog(); 
  }

  // Store colors per PO
rowColorMap: { [poOrderNo: string]: string } = {};
colors = ['#9ae2dfff', '#e6f7ff']; // alternating colors

getRowBgColor(po: any): string {
  const key = po.po_order_no; // group by po_order_no

  if (!this.rowColorMap[key]) {
    // assign next color based on existing groups
    const nextColorIndex = Object.keys(this.rowColorMap).length % this.colors.length;
    this.rowColorMap[key] = this.colors[nextColorIndex];
  }

  return this.rowColorMap[key];
}

// Component.ts
getHoverInfo(po: any): string {
  return `Product Code: ${po.parent_product_code}
Product Name: ${po.parent_name}
Plan Qty: ${po.plan_qty} ${po.planUnit} `;
}


  result;
  getPOsLog(){
    this.service.get('marketing/po.php?type=getPOsLog').subscribe(response =>{
      this.result =response;
    });
  }

 
  isView = false;
  selectedResult = [];

  view(data){
    this.selectedResult = data;
    this.isView = true;
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

 
   
}



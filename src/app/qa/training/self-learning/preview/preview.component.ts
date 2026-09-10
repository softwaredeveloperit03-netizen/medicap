import { AfterContentInit, AfterViewInit, Component, Input, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-preview',
  templateUrl: './preview.component.html',
  styleUrls: ['./preview.component.css']
})
export class PreviewComponent implements OnInit, AfterViewInit{
  page = 1;
  totalPages: number;
  pageSize = 10; // Number of pages displayed per "page" in the visible page list (adjust as needed)
  visiblePages: number[];
  currentPage:any;
  urlPath: any;
  @Input() pdfUrl: string;
  
  constructor(private service:DataAccessService) { }
  ngOnInit(): void {
  }
  
  ngAfterViewInit(): void {
    this.urlPath = this.service.url + '../../upload/training/' + this.pdfUrl;
  }

  afterLoadComplete(pdfData: any) {
    this.totalPages = pdfData.numPages;
    this.updateVisiblePages(); // Update visible pages on load after PDF is loaded
  }

  updateVisiblePages() {
    const startIndex = Math.max(1, this.page - Math.floor(this.pageSize / 2));
    const endIndex = Math.min(this.totalPages, startIndex + this.pageSize - 1);
    this.visiblePages = Array.from({ length: endIndex - startIndex + 1 }, (_, i) => startIndex + i);
  }

  previousPage() {
    if (this.page > 1) {
      this.page--;
      this.updateVisiblePages();
    }
  }

  nextPage() {
    if (this.page) {
      this.page++;
      this.updateVisiblePages();
    }
  }


  goToPage(pageNumber: number) {
    this.currentPage = pageNumber;
    if (pageNumber >= 1 && pageNumber <= this.totalPages) {
      this.page = pageNumber;
      this.updateVisiblePages();
    }
  }
}

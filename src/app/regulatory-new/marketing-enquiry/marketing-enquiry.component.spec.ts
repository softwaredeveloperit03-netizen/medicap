import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MarketingEnquiryComponent } from './marketing-enquiry.component';

describe('MarketingEnquiryComponent', () => {
  let component: MarketingEnquiryComponent;
  let fixture: ComponentFixture<MarketingEnquiryComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MarketingEnquiryComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MarketingEnquiryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

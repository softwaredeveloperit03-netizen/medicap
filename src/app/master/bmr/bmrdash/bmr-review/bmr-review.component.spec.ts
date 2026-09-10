import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BmrReviewComponent } from './bmr-review.component';

describe('BmrReviewComponent', () => {
  let component: BmrReviewComponent;
  let fixture: ComponentFixture<BmrReviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BmrReviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BmrReviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

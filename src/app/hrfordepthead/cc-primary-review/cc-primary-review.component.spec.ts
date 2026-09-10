import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CcPrimaryReviewComponent } from './cc-primary-review.component';

describe('CcPrimaryReviewComponent', () => {
  let component: CcPrimaryReviewComponent;
  let fixture: ComponentFixture<CcPrimaryReviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CcPrimaryReviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CcPrimaryReviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

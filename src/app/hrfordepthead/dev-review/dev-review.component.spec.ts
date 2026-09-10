import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DevReviewComponent } from './dev-review.component';

describe('DevReviewComponent', () => {
  let component: DevReviewComponent;
  let fixture: ComponentFixture<DevReviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DevReviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DevReviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

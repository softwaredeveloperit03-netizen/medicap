import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReviewQaComponent } from './review-qa.component';

describe('ReviewQaComponent', () => {
  let component: ReviewQaComponent;
  let fixture: ComponentFixture<ReviewQaComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ReviewQaComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ReviewQaComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

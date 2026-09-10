import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FinalreviewComponent } from './finalreview.component';

describe('FinalreviewComponent', () => {
  let component: FinalreviewComponent;
  let fixture: ComponentFixture<FinalreviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FinalreviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FinalreviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

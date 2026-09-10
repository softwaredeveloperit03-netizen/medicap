import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DeptheadreviewComponent } from './deptheadreview.component';

describe('DeptheadreviewComponent', () => {
  let component: DeptheadreviewComponent;
  let fixture: ComponentFixture<DeptheadreviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DeptheadreviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DeptheadreviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

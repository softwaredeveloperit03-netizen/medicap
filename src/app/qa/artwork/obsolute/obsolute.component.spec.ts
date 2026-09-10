import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ObsoluteComponent } from './obsolute.component';

describe('ObsoluteComponent', () => {
  let component: ObsoluteComponent;
  let fixture: ComponentFixture<ObsoluteComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ObsoluteComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ObsoluteComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

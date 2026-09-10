import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AttenanceComponent } from './attenance.component';

describe('AttenanceComponent', () => {
  let component: AttenanceComponent;
  let fixture: ComponentFixture<AttenanceComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AttenanceComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AttenanceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

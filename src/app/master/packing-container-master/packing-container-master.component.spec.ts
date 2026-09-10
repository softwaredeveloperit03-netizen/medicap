import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PackingCOntainerMasterComponent } from './packing-container-master.component';

describe('PackingCOntainerMasterComponent', () => {
  let component: PackingCOntainerMasterComponent;
  let fixture: ComponentFixture<PackingCOntainerMasterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PackingCOntainerMasterComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PackingCOntainerMasterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

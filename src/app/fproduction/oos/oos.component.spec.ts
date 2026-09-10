import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OosComponent } from './oos.component';

describe('OosComponent', () => {
  let component: OosComponent;
  let fixture: ComponentFixture<OosComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OosComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OosComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

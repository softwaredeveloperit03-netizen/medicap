import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OinitiateComponent } from './oinitiate.component';

describe('OinitiateComponent', () => {
  let component: OinitiateComponent;
  let fixture: ComponentFixture<OinitiateComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OinitiateComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OinitiateComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

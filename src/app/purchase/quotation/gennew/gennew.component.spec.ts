import { ComponentFixture, TestBed } from '@angular/core/testing';

import { GennewComponent } from './gennew.component';

describe('GennewComponent', () => {
  let component: GennewComponent;
  let fixture: ComponentFixture<GennewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ GennewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(GennewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

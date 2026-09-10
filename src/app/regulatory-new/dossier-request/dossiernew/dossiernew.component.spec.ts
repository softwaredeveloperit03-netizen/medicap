import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DossiernewComponent } from './dossiernew.component';

describe('DossiernewComponent', () => {
  let component: DossiernewComponent;
  let fixture: ComponentFixture<DossiernewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DossiernewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DossiernewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FtirComponent } from './ftir.component';

describe('FtirComponent', () => {
  let component: FtirComponent;
  let fixture: ComponentFixture<FtirComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FtirComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(FtirComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EnvLibraryComponent } from './env-library.component';

describe('EnvLibraryComponent', () => {
  let component: EnvLibraryComponent;
  let fixture: ComponentFixture<EnvLibraryComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EnvLibraryComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(EnvLibraryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

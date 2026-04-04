<?php

use App\Http\Controllers\AbonneAssuranceController;
use App\Http\Controllers\AbonneController;
use App\Http\Controllers\AbonnementController;
use App\Http\Controllers\ActiviteController;
use App\Http\Controllers\AssuranceCompanyController;
use App\Http\Controllers\CoachController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\PointageController;
use App\Http\Controllers\ReclamationAssuranceController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('root');
Route::get('/home', [HomeController::class, 'index'])->name('home');

Auth::routes();

Route::middleware('auth')->group(function () {
    Route::get('/zk-status', [AbonneController::class, 'checkZkStatus'])->name('zk.status');

    Route::prefix('abonnes')->name('abonnes.')->group(function () {
        Route::get('/', [AbonneController::class, 'index'])->name('index');
        Route::get('/getData', [AbonneController::class, 'getData'])->name('getData');
        Route::post('/import-zk-users', [AbonneController::class, 'importZkUsers'])->name('import-zk-users');
        Route::post('/sync-zk', [AbonneController::class, 'syncAllZKTeco'])->name('sync-all-zk');
        Route::post('/', [AbonneController::class, 'store'])->name('store');
        Route::get('/export', [AbonneController::class, 'export'])->name('export');
        Route::get('/{abonne}', [AbonneController::class, 'show'])->name('show');
        Route::get('/{abonne}/edit', [AbonneController::class, 'edit'])->name('edit');
        Route::put('/{abonne}', [AbonneController::class, 'update'])->name('update');
        Route::delete('/{abonne}', [AbonneController::class, 'destroy'])->name('destroy');
        Route::post('/{abonne}/sync-zk', [AbonneController::class, 'syncZKTeco'])->name('sync-zk');
    });

    Route::prefix('activites')->name('activites.')->group(function () {
        Route::get('/', [ActiviteController::class, 'index'])->name('index');
        Route::get('/getData', [ActiviteController::class, 'getData'])->name('getData');
        Route::get('/export', [ActiviteController::class, 'export'])->name('export');
        Route::post('/', [ActiviteController::class, 'store'])->name('store');
        Route::get('/{activite}', [ActiviteController::class, 'show'])->name('show');
        Route::get('/{activite}/edit', [ActiviteController::class, 'edit'])->name('edit');
        Route::put('/{activite}', [ActiviteController::class, 'update'])->name('update');
        Route::delete('/{activite}', [ActiviteController::class, 'destroy'])->name('destroy');
        Route::get('/{activite}/get-prix', [ActiviteController::class, 'getPrix'])->name('get-prix');
    });

    Route::prefix('coaches')->name('coaches.')->group(function () {
        Route::get('/', [CoachController::class, 'index'])->name('index');
        Route::get('/getData', [CoachController::class, 'getData'])->name('getData');
        Route::post('/', [CoachController::class, 'store'])->name('store');
        Route::get('/export', [CoachController::class, 'export'])->name('export');
        Route::get('/{coach}', [CoachController::class, 'show'])->name('show');
        Route::get('/{coach}/edit', [CoachController::class, 'edit'])->name('edit');
        Route::put('/{coach}', [CoachController::class, 'update'])->name('update');
        Route::delete('/{coach}', [CoachController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [AbonnementController::class, 'index'])->name('index');
        Route::get('/getData', [AbonnementController::class, 'getData'])->name('getData');
        Route::post('/', [AbonnementController::class, 'store'])->name('store');
        Route::get('/export', [AbonnementController::class, 'export'])->name('export');
        Route::get('/{subscription}', [AbonnementController::class, 'show'])->name('show');
        Route::get('/{subscription}/edit', [AbonnementController::class, 'edit'])->name('edit');
        Route::put('/{subscription}', [AbonnementController::class, 'update'])->name('update');
        Route::delete('/{subscription}', [AbonnementController::class, 'destroy'])->name('destroy');
        Route::post('/{subscription}/renew', [AbonnementController::class, 'renew'])->name('renew');
        Route::post('/{subscription}/change-status', [AbonnementController::class, 'changeStatus'])->name('change-status');
    });

    Route::prefix('abonnements')->name('abonnements.')->group(function () {
        Route::get('/', [AbonnementController::class, 'index'])->name('index');
        Route::get('/getData', [AbonnementController::class, 'getData'])->name('getData');
        Route::post('/', [AbonnementController::class, 'store'])->name('store');
        Route::get('/export', [AbonnementController::class, 'export'])->name('export');
        Route::get('/{subscription}', [AbonnementController::class, 'show'])->name('show');
        Route::get('/{subscription}/edit', [AbonnementController::class, 'edit'])->name('edit');
        Route::put('/{subscription}', [AbonnementController::class, 'update'])->name('update');
        Route::delete('/{subscription}', [AbonnementController::class, 'destroy'])->name('destroy');
        Route::post('/{subscription}/renew', [AbonnementController::class, 'renew'])->name('renew');
        Route::post('/{subscription}/change-status', [AbonnementController::class, 'changeStatus'])->name('change-status');
    });

    Route::prefix('paiements')->name('paiements.')->group(function () {
        Route::get('/', [PaiementController::class, 'index'])->name('index');
        Route::get('/getData', [PaiementController::class, 'getData'])->name('getData');
        Route::post('/', [PaiementController::class, 'store'])->name('store');
        Route::get('/export', [PaiementController::class, 'export'])->name('export');
        Route::get('/statistiques', [PaiementController::class, 'statistiques'])->name('statistiques');
        Route::get('/{paiement}', [PaiementController::class, 'show'])->name('show');
        Route::get('/{paiement}/edit', [PaiementController::class, 'edit'])->name('edit');
        Route::put('/{paiement}', [PaiementController::class, 'update'])->name('update');
        Route::delete('/{paiement}', [PaiementController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('pointages')->name('pointages.')->group(function () {
        Route::get('/', [PointageController::class, 'index'])->name('index');
        Route::get('/getData', [PointageController::class, 'getData'])->name('getData');
        Route::post('/', [PointageController::class, 'store'])->name('store');
        Route::post('/import-zk', [PointageController::class, 'importZKTeco'])->name('import-zk');
        Route::get('/export', [PointageController::class, 'export'])->name('export');
        Route::get('/statistiques', [PointageController::class, 'statistiques'])->name('statistiques');
        Route::get('/{pointage}', [PointageController::class, 'show'])->name('show');
        Route::delete('/{pointage}', [PointageController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('assurance-companies')->name('assurance_companies.')->group(function () {
        Route::get('/', [AssuranceCompanyController::class, 'index'])->name('index');
        Route::get('/getData', [AssuranceCompanyController::class, 'getData'])->name('getData');
        Route::post('/', [AssuranceCompanyController::class, 'store'])->name('store');
        Route::get('/export', [AssuranceCompanyController::class, 'export'])->name('export');
        Route::get('/{assurance_company}', [AssuranceCompanyController::class, 'show'])->name('show');
        Route::get('/{assurance_company}/edit', [AssuranceCompanyController::class, 'edit'])->name('edit');
        Route::put('/{assurance_company}', [AssuranceCompanyController::class, 'update'])->name('update');
        Route::delete('/{assurance_company}', [AssuranceCompanyController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('abonne-assurances')->name('abonne_assurances.')->group(function () {
        Route::get('/', [AbonneAssuranceController::class, 'index'])->name('index');
        Route::get('/getData', [AbonneAssuranceController::class, 'getData'])->name('getData');
        Route::post('/', [AbonneAssuranceController::class, 'store'])->name('store');
        Route::get('/export', [AbonneAssuranceController::class, 'export'])->name('export');
        Route::get('/{abonne_assurance}', [AbonneAssuranceController::class, 'show'])->name('show');
        Route::get('/{abonne_assurance}/edit', [AbonneAssuranceController::class, 'edit'])->name('edit');
        Route::put('/{abonne_assurance}', [AbonneAssuranceController::class, 'update'])->name('update');
        Route::delete('/{abonne_assurance}', [AbonneAssuranceController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('reclamation-assurances')->name('reclamation_assurances.')->group(function () {
        Route::get('/', [ReclamationAssuranceController::class, 'index'])->name('index');
        Route::get('/getData', [ReclamationAssuranceController::class, 'getData'])->name('getData');
        Route::get('/export', [ReclamationAssuranceController::class, 'export'])->name('export');
        Route::post('/', [ReclamationAssuranceController::class, 'store'])->name('store');
        Route::get('/{reclamation_assurance}', [ReclamationAssuranceController::class, 'show'])->whereNumber('reclamation_assurance')->name('show');
        Route::get('/{reclamation_assurance}/edit', [ReclamationAssuranceController::class, 'edit'])->whereNumber('reclamation_assurance')->name('edit');
        Route::put('/{reclamation_assurance}', [ReclamationAssuranceController::class, 'update'])->whereNumber('reclamation_assurance')->name('update');
        Route::delete('/{reclamation_assurance}', [ReclamationAssuranceController::class, 'destroy'])->whereNumber('reclamation_assurance')->name('destroy');
        Route::post('/{reclamation_assurance}/traiter', [ReclamationAssuranceController::class, 'traiter'])->whereNumber('reclamation_assurance')->name('traiter');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/', [SettingController::class, 'store'])->name('store');
        Route::put('/{setting}', [SettingController::class, 'update'])->name('update');
    });

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [HomeController::class, 'dashboard'])->name('index');
        Route::get('/statistiques', [HomeController::class, 'statistiques'])->name('statistiques');
        Route::get('/rapports', [HomeController::class, 'rapports'])->name('rapports');
    });

    Route::prefix('api')->name('api.')->group(function () {
        Route::post('/zkteco/sync', [PointageController::class, 'syncZKTecoAPI'])->name('zkteco.sync');
        Route::get('/zkteco/abonnes', [AbonneController::class, 'getAbonnesForZKTeco'])->name('zkteco.abonnes');
    });

    Route::prefix('rapports')->name('rapports.')->group(function () {
        Route::get('/financier', [HomeController::class, 'rapportFinancier'])->name('financier');
        Route::get('/frequentation', [HomeController::class, 'rapportFrequentation'])->name('frequentation');
        Route::get('/assurances', [HomeController::class, 'rapportAssurances'])->name('assurances');
        Route::get('/subscriptions', [HomeController::class, 'rapportSubscriptions'])->name('subscriptions');
    });

    Route::prefix('import-export')->name('import_export.')->group(function () {
        Route::get('/export/abonnes', [AbonneController::class, 'export'])->name('abonnes');
        Route::post('/import/abonnes', [AbonneController::class, 'import'])->name('import.abonnes');
        Route::get('/export/paiements', [PaiementController::class, 'export'])->name('paiements');
        Route::get('/export/pointages', [PointageController::class, 'export'])->name('pointages');
    });
});
